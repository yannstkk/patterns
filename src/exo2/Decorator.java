package src.exo2;

abstract  class Decorator extends  Commande{

    protected  Commande cmd; 

    public Decorator(Commande commande) {
        this.cmd = commande;
    }

    @Override
    public int calculatePrice() {
        return cmd.calculatePrice();
    }
    
}
